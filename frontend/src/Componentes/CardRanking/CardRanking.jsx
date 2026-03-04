

function CardRanking({props}) {
    return(
        <>
        <div className={`w-full shadow-md p-5 flex justify-between mb-3 bg-gradient-to-r rounded-lg   ${props.posicao === "1" ? 'from-[#a48d42] to-white' : props.posicao === "2" ? 'from-[#8a8a8a] to-white' : props.posicao === "3" ? 'from-[#be854f] to-white' : 'from-gray-200 to-white'}`}>
                <div className="flex items-center gap-4">
                    <img src="/icons/medalha.svg" alt="Icone Medalha" className="w-8" />
                    <h2 className="text-2xl">{props.posicao}º</h2>
                    <h3 className="text-xl">{props.nome}</h3>
                </div>
                <h2 className="text-2xl">{props.pontos} pts</h2>
            </div>
        </>
    )
}

export default CardRanking
