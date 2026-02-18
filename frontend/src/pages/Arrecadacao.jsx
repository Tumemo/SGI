import { useEffect } from "react"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import NavBar from "../components/NavBar/NavBar"

function Arrecadacao() {
    useEffect(() => {
        document.title = "SGM - Arrecadação"
    },[])

    return (
        <>
            <BannerGlobal titulo="Arrecadações" />
            <NavBar />
        </>
    )
}

export default Arrecadacao