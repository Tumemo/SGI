import TextTop from '../Componentes/TextTop/TextTop'
import BannerGlobal from "../Componentes/BannerGlobal/BannerGlobal"
import NavBar from "../Componentes/NavBar/NavBar"
import CardInfo from '../Componentes/CardInfo/CardInfo'
import { useEffect } from 'react'

function Modalidades(){
    useEffect(()=>{
        document.title = "SGM - Modalidades"
    },[])

    return(
        <>
            <BannerGlobal titulo="Modalidades" voltar="/Edicao/2"/>
            <TextTop texto="Escolha uma modalidade para editar"/>
            <main className='w-[80%] m-auto relative'>
                <CardInfo titulo="Basquete" icon="/icons/bolaBasquete-icon.svg" alt="Icone de Basquete"/>
                <CardInfo titulo="Corrida" icon="/icons/trophy-icon.svg" alt="Icone de Corrida"/>
                <CardInfo titulo="Futsal" icon="/icons/bolaFutsal-icon.svg" alt="Icone de Futsal"/>
                <button className='rounded-full bg-red-500 p-2.5 absolute -bottom-2/3 right-0'><img src="/icons/plus-icon.svg" alt="Icone de mais" /></button>
            </main>
            <NavBar />
        </>
    )
}

export default Modalidades